import sys
import json
import mysql.connector

def naive_bayes_classification(data):
    hasil_klasifikasi = []

    for karyawan in data:
        usia = karyawan['usia']
        jenis_kelamin = karyawan['jenis_kelamin']
        kriteria_values = karyawan['kriteria']

        # Implementasi Naive Bayes sederhana untuk ilustrasi
        if jenis_kelamin == 'Laki-laki':
            hasil = 'Naik' if int(usia) >= 30 and kriteria_values.get('1') == 'Tinggi' else 'Tidak Naik'
        else:
            hasil = 'Naik' if int(usia) >= 25 and kriteria_values.get('1') == 'Tinggi' else 'Tidak Naik'

        hasil_klasifikasi.append({
            'karyawan_id': karyawan['karyawan_id'],
            'hasil': hasil
        })

    return hasil_klasifikasi

# Main program
if __name__ == "__main__":
    data = json.loads(sys.argv[1])

    hasil_klasifikasi = naive_bayes_classification(data)

    print(json.dumps(hasil_klasifikasi))
